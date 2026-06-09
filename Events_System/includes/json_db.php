<?php

/**
 * JSON file-based data store (replaces Supabase/remote database).
 * API mirrors SupabaseClient so existing pages work unchanged.
 */
class JsonDataClient {
    private $dataDir;

    public function __construct($dataDir) {
        $this->dataDir = rtrim($dataDir, '/\\');
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0777, true);
        }
    }

    private function readTable($table) {
        $file = $this->dataDir . '/' . $table . '.json';
        if (!file_exists($file)) {
            return [];
        }
        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }

    private function writeTable($table, $data) {
        $file = $this->dataDir . '/' . $table . '.json';
        file_put_contents(
            $file,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    private function generateId() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    private function parseFilterValue($value) {
        if (preg_match('/^(eq|gte|lte|gt|lt|ilike)\.(.+)$/s', $value, $m)) {
            return ['op' => $m[1], 'val' => urldecode($m[2])];
        }
        return ['op' => 'eq', 'val' => $value];
    }

    private function matchFilter($row, $field, $filterValue) {
        $parsed = $this->parseFilterValue($filterValue);
        $op = $parsed['op'];
        $val = $parsed['val'];
        $rowVal = $row[$field] ?? null;

        switch ($op) {
            case 'eq':
                return (string)$rowVal === (string)$val;
            case 'gte':
                return $rowVal >= $val;
            case 'lte':
                return $rowVal <= $val;
            case 'gt':
                return $rowVal > $val;
            case 'lt':
                return $rowVal < $val;
            case 'ilike':
                $pattern = str_replace('%', '', $val);
                return stripos((string)$rowVal, $pattern) !== false;
            default:
                return false;
        }
    }

    private function applyFilters($rows, $filters) {
        foreach ($filters as $key => $value) {
            if ($key === 'or') {
                continue;
            }
            $rows = array_values(array_filter($rows, function ($row) use ($key, $value) {
                return $this->matchFilter($row, $key, $value);
            }));
        }
        if (isset($filters['or'])) {
            $orExpr = $filters['or'];
            if (preg_match('/^\((.+)\)$/', $orExpr, $m)) {
                $conditions = $this->parseOrConditions($m[1]);
                $rows = array_values(array_filter($rows, function ($row) use ($conditions) {
                    foreach ($conditions as $cond) {
                        if ($this->matchNestedOrFilter($row, $cond)) {
                            return true;
                        }
                    }
                    return false;
                }));
            }
        }
        return $rows;
    }

    private function parseOrConditions($expr) {
        $conditions = [];
        $parts = preg_split('/,(?=[^)]*(?:\(|$))/', $expr);
        foreach ($parts as $part) {
            if (preg_match('/^(.+)\.(eq|gte|lte|gt|lt|ilike)\.(.+)$/s', trim($part), $m)) {
                $conditions[] = ['field' => $m[1], 'op' => $m[2], 'val' => urldecode($m[3])];
            }
        }
        return $conditions;
    }

    private function matchNestedOrFilter($row, $cond) {
        $field = $cond['field'];
        if (strpos($field, '.') !== false) {
            $parts = explode('.', $field, 2);
            $related = $this->getRelated($row, $parts[0], [$parts[1]]);
            if (is_array($related) && array_key_exists($parts[1], $related)) {
                return $this->matchFilter(
                    [$parts[1] => $related[$parts[1]]],
                    $parts[1],
                    $cond['op'] . '.' . $cond['val']
                );
            }
            return false;
        }
        return $this->matchFilter($row, $field, $cond['op'] . '.' . $cond['val']);
    }

    private function applyOrder($rows, $order) {
        if (!$order) {
            return $rows;
        }
        $parts = explode(',', $order);
        usort($rows, function ($a, $b) use ($parts) {
            foreach ($parts as $part) {
                $part = trim($part);
                $dir = 'asc';
                if (preg_match('/^(.+)\.(asc|desc)$/i', $part, $m)) {
                    $field = $m[1];
                    $dir = strtolower($m[2]);
                } else {
                    $field = $part;
                }
                $va = $a[$field] ?? '';
                $vb = $b[$field] ?? '';
                if ($va == $vb) {
                    continue;
                }
                $cmp = $va <=> $vb;
                return $dir === 'desc' ? -$cmp : $cmp;
            }
            return 0;
        });
        return $rows;
    }

    private function parseSelect($select) {
        $columns = [];
        $embeds = [];
        if ($select === '*') {
            return ['columns' => '*', 'embeds' => []];
        }
        $tokens = $this->splitSelect($select);
        foreach ($tokens as $token) {
            if (preg_match('/^(\w+)\((.+)\)$/', $token, $m)) {
                $embeds[$m[1]] = $this->parseEmbedFields($m[2]);
            } else {
                $columns[] = $token;
            }
        }
        return ['columns' => $columns, 'embeds' => $embeds];
    }

    private function splitSelect($select) {
        $tokens = [];
        $current = '';
        $depth = 0;
        $len = strlen($select);
        for ($i = 0; $i < $len; $i++) {
            $ch = $select[$i];
            if ($ch === '(') {
                $depth++;
            } elseif ($ch === ')') {
                $depth--;
            } elseif ($ch === ',' && $depth === 0) {
                $tokens[] = trim($current);
                $current = '';
                continue;
            }
            $current .= $ch;
        }
        if (trim($current) !== '') {
            $tokens[] = trim($current);
        }
        return $tokens;
    }

    private function parseEmbedFields($fields) {
        return array_map('trim', explode(',', $fields));
    }

    private function getRelated($row, $relation, $fields) {
        switch ($relation) {
            case 'events':
                $events = $this->readTable('events');
                foreach ($events as $ev) {
                    if ($ev['id'] === ($row['event_id'] ?? null)) {
                        return $this->pickFields($ev, $fields);
                    }
                }
                return null;
            case 'participants':
                $participants = $this->readTable('participants');
                foreach ($participants as $p) {
                    if ($p['id'] === ($row['participant_id'] ?? null)) {
                        return $this->pickFields($p, $fields);
                    }
                }
                return null;
            case 'feedbacks':
                $feedbacks = $this->readTable('feedbacks');
                $result = [];
                foreach ($feedbacks as $fb) {
                    if ($fb['event_id'] === ($row['id'] ?? null)) {
                        $result[] = $this->pickFields($fb, $fields);
                    }
                }
                return $result;
            default:
                return null;
        }
    }

    private function pickFields($row, $fields) {
        if (in_array('*', $fields, true)) {
            return $row;
        }
        $picked = [];
        foreach ($fields as $f) {
            if (isset($row[$f])) {
                $picked[$f] = $row[$f];
            }
        }
        return $picked;
    }

    private function shapeRows($rows, $select, $table) {
        if ($select === 'count') {
            return [['count' => count($rows)]];
        }
        $parsed = $this->parseSelect($select);
        $result = [];
        foreach ($rows as $row) {
            if ($parsed['columns'] === '*') {
                $item = $row;
            } elseif (count($parsed['columns']) === 1 && $parsed['columns'][0] !== '*') {
                $col = $parsed['columns'][0];
                $item = [$col => $row[$col] ?? null];
            } else {
                $item = $this->pickFields($row, $parsed['columns']);
            }
            foreach ($parsed['embeds'] as $rel => $fields) {
                $item[$rel] = $this->getRelated($row, $rel, $fields);
            }
            $result[] = $item;
        }
        return $result;
    }

    private function parseQueryString($endpoint) {
        $parts = explode('?', $endpoint, 2);
        $table = $parts[0];
        $params = [];
        if (!isset($parts[1])) {
            return [$table, $params];
        }

        $qs = $parts[1];
        if (preg_match('/(?:^|&)or=\((.+)\)(?:&|$)/', $qs, $m)) {
            $params['or'] = '(' . $m[1] . ')';
            $qs = preg_replace('/&?or=\(.+\)/', '', $qs);
        }

        $chunks = preg_split('/&(?=[^)]*(?:\(|$))/', $qs);
        foreach ($chunks as $chunk) {
            if ($chunk === '') {
                continue;
            }
            $eqPos = strpos($chunk, '=');
            if ($eqPos === false) {
                continue;
            }
            $key = substr($chunk, 0, $eqPos);
            $value = urldecode(substr($chunk, $eqPos + 1));
            $params[$key] = $value;
        }

        return [$table, $params];
    }

    public function select($table, $columns = '*', $filters = [], $order = null, $limit = null) {
        $rows = $this->readTable($table);
        $rows = $this->applyFilters($rows, $filters);
        $rows = $this->applyOrder($rows, $order);
        if ($limit !== null) {
            $rows = array_slice($rows, 0, (int)$limit);
        }
        return ['data' => $this->shapeRows($rows, $columns, $table), 'error' => null];
    }

    public function query($endpoint) {
        [$table, $params] = $this->parseQueryString($endpoint);
        $select = $params['select'] ?? '*';
        unset($params['select'], $params['group']);

        $order = $params['order'] ?? null;
        unset($params['order']);

        $isCountGroup = ($select !== 'count' && strpos($select, 'count=count()') !== false);

        if ($isCountGroup) {
            preg_match('/^(\w+),count=count\(\)$/', $select, $gm);
            $groupField = $gm[1] ?? null;
            $rows = $this->readTable($table);
            $rows = $this->applyFilters($rows, $params);
            $groups = [];
            foreach ($rows as $row) {
                $key = $row[$groupField] ?? 'unknown';
                if (!isset($groups[$key])) {
                    $groups[$key] = 0;
                }
                $groups[$key]++;
            }
            $result = [];
            foreach ($groups as $key => $count) {
                $result[] = [$groupField => $key, 'count' => $count];
            }
            return ['data' => $result, 'error' => null];
        }

        if ($select === 'count' || preg_match('/count=count\(\)/', $select)) {
            $rows = $this->readTable($table);
            $rows = $this->applyFilters($rows, $params);
            return ['data' => [['count' => count($rows)]], 'error' => null];
        }

        $filters = $params;
        $rows = $this->readTable($table);
        $rows = $this->applyFilters($rows, $filters);
        $rows = $this->applyOrder($rows, $order);

        return ['data' => $this->shapeRows($rows, $select, $table), 'error' => null];
    }

    public function insert($table, $data) {
        $rows = $this->readTable($table);
        $record = is_array($data) && isset($data[0]) ? $data : [$data];

        foreach ($record as $item) {
            if ($table === 'admins') {
                foreach ($rows as $existing) {
                    if ($existing['username'] === $item['username'] || $existing['email'] === $item['email']) {
                        return ['data' => null, 'error' => ['message' => 'Username or email already exists']];
                    }
                }
            }

            if (!isset($item['id'])) {
                $item['id'] = $this->generateId();
            }
            $now = date('c');
            if ($table === 'events' && !isset($item['created_at'])) {
                $item['created_at'] = $now;
            }
            if ($table === 'admins' && !isset($item['created_at'])) {
                $item['created_at'] = $now;
            }
            if ($table === 'participants' && !isset($item['registration_date'])) {
                $item['registration_date'] = $now;
            }
            if ($table === 'feedbacks' && !isset($item['submitted_at'])) {
                $item['submitted_at'] = $now;
            }
            $rows[] = $item;
        }

        $this->writeTable($table, $rows);
        return ['data' => $record, 'error' => null];
    }

    public function update($table, $data, $filters) {
        $rows = $this->readTable($table);
        $updated = false;
        foreach ($rows as &$row) {
            $match = true;
            foreach ($filters as $key => $value) {
                if (!$this->matchFilter($row, $key, $value)) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                foreach ($data as $k => $v) {
                    $row[$k] = $v;
                }
                $updated = true;
            }
        }
        unset($row);
        if ($updated) {
            $this->writeTable($table, $rows);
        }
        return ['data' => null, 'error' => null];
    }

    public function delete($table, $filters) {
        $rows = $this->readTable($table);
        $deletedIds = [];
        $remaining = [];
        foreach ($rows as $row) {
            $match = true;
            foreach ($filters as $key => $value) {
                if (!$this->matchFilter($row, $key, $value)) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                $deletedIds[] = $row['id'] ?? null;
            } else {
                $remaining[] = $row;
            }
        }

        if ($table === 'events') {
            foreach ($deletedIds as $eventId) {
                $this->cascadeDeleteEvent($eventId);
            }
        } elseif ($table === 'participants') {
            foreach ($deletedIds as $participantId) {
                $this->cascadeDeleteParticipant($participantId);
            }
        }

        $this->writeTable($table, $remaining);
        return ['data' => null, 'error' => null];
    }

    private function cascadeDeleteEvent($eventId) {
        $participants = $this->readTable('participants');
        $participants = array_values(array_filter($participants, function ($p) use ($eventId) {
            return $p['event_id'] !== $eventId;
        }));
        $this->writeTable('participants', $participants);

        $feedbacks = $this->readTable('feedbacks');
        $feedbacks = array_values(array_filter($feedbacks, function ($f) use ($eventId) {
            return $f['event_id'] !== $eventId;
        }));
        $this->writeTable('feedbacks', $feedbacks);
    }

    private function cascadeDeleteParticipant($participantId) {
        $feedbacks = $this->readTable('feedbacks');
        $feedbacks = array_values(array_filter($feedbacks, function ($f) use ($participantId) {
            return $f['participant_id'] !== $participantId;
        }));
        $this->writeTable('feedbacks', $feedbacks);
    }

    public function rpc($function, $params = []) {
        return ['data' => null, 'error' => ['message' => 'RPC not supported in JSON mode']];
    }
}
