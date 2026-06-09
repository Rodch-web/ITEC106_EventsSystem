const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const projectRoot = __dirname;
const errors = [];
const warnings = [];

console.log('CEMS Build - Validating PHP Project...\n');

// 1. Check all required files exist
const requiredFiles = [
  'index.php', 'about.php', 'events.php', 'event_details.php', 'register.php', 'confirmation.php', 'feedback.php',
  'includes/config.php', 'includes/header.php', 'includes/footer.php',
  'includes/admin_header.php', 'includes/admin_footer.php',
  'admin/login.php', 'admin/register.php', 'admin/logout.php',
  'admin/dashboard.php', 'admin/events.php', 'admin/participants.php',
  'admin/feedbacks.php', 'admin/reports.php',
  'assets/css/style.css', 'assets/js/main.js',
  'assets/images/event-placeholder.svg',
];

for (const file of requiredFiles) {
  const fullPath = path.join(projectRoot, file);
  if (!fs.existsSync(fullPath)) {
    errors.push(`Missing required file: ${file}`);
  }
}

// 2. Syntax check all PHP files
const phpFiles = [];
function collectPhpFiles(dir) {
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);
    if (entry.isDirectory() && !entry.name.startsWith('.') && !entry.name.includes('node_modules')) {
      collectPhpFiles(fullPath);
    } else if (entry.isFile() && entry.name.endsWith('.php')) {
      phpFiles.push(fullPath);
    }
  }
}
collectPhpFiles(projectRoot);

for (const phpFile of phpFiles) {
  try {
    execSync(`php -l "${phpFile}"`, { stdio: 'pipe' });
    console.log(`  OK  ${path.relative(projectRoot, phpFile)}`);
  } catch (e) {
    errors.push(`Syntax error in ${path.relative(projectRoot, phpFile)}`);
  }
}

// 3. Check CSS file exists
const cssPath = path.join(projectRoot, 'assets/css/style.css');
if (!fs.existsSync(cssPath)) {
  errors.push('Missing CSS file');
}

// 4. Check JS file exists
const jsPath = path.join(projectRoot, 'assets/js/main.js');
if (!fs.existsSync(jsPath)) {
  errors.push('Missing main.js');
}

// 5. Summary
console.log('\n--- Build Summary ---');
console.log(`PHP files checked: ${phpFiles.length}`);
console.log(`Required files: ${requiredFiles.length}`);
console.log(`Errors: ${errors.length}`);
console.log(`Warnings: ${warnings.length}`);

if (errors.length > 0) {
  console.log('\nERRORS:');
  errors.forEach(e => console.log(`  - ${e}`));
  process.exit(1);
}

if (warnings.length > 0) {
  console.log('\nWARNINGS:');
  warnings.forEach(w => console.log(`  - ${w}`));
}

console.log('\nBuild successful. Project is ready for deployment.');
