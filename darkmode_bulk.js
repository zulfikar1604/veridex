const fs = require('fs');
const path = require('path');

const dir = 'd:/xampp/htdocs/veridex';

function processFile(filePath) {
    if (!filePath.endsWith('.php')) return;
    
    // Ignore some files that don't need UI dark mode or are already done
    const basename = path.basename(filePath);
    if (basename === 'header_meta.php' || basename === 'database.php') return;

    let content = fs.readFileSync(filePath, 'utf8');
    let originalContent = content;

    // Body
    content = content.replace(/body class="bg-slate-50(?! dark:)/g, 'body class="bg-slate-50 dark:bg-[#0f172a]');

    // Cards and white backgrounds
    content = content.replace(/bg-white(?! dark:)/g, 'bg-white dark:bg-[#1e293b]');
    
    // Slate-50 background (often used for headers of cards)
    content = content.replace(/bg-slate-50(?! dark:)/g, 'bg-slate-50 dark:bg-slate-900');
    
    // Borders
    content = content.replace(/border-slate-100(?! dark:)/g, 'border-slate-100 dark:border-slate-800');
    content = content.replace(/border-slate-200(?! dark:)/g, 'border-slate-200 dark:border-slate-700');
    
    // Text colors
    content = content.replace(/text-slate-800(?! dark:)/g, 'text-slate-800 dark:text-white');
    content = content.replace(/text-slate-700(?! dark:)/g, 'text-slate-700 dark:text-slate-200');
    content = content.replace(/text-slate-600(?! dark:)/g, 'text-slate-600 dark:text-slate-300');
    content = content.replace(/text-slate-500(?! dark:)/g, 'text-slate-500 dark:text-slate-400');
    content = content.replace(/text-slate-400(?! dark:)/g, 'text-slate-400 dark:text-slate-500');

    if (content !== originalContent) {
        fs.writeFileSync(filePath, content, 'utf8');
        console.log(`Updated: ${basename}`);
    }
}

function walkDir(currentDir) {
    const files = fs.readdirSync(currentDir);
    for (const file of files) {
        const fullPath = path.join(currentDir, file);
        const stat = fs.statSync(fullPath);
        if (stat.isDirectory() && file !== '.git' && file !== 'node_modules' && file !== 'uploads') {
            walkDir(fullPath);
        } else if (stat.isFile()) {
            processFile(fullPath);
        }
    }
}

walkDir(dir);
console.log('Bulk Dark Mode applied!');
