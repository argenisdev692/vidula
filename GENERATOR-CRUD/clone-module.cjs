const fs = require('fs');
const path = require('path');

const srcModuleName = process.argv[2];
const destModuleName = process.argv[3];   // e.g. Products
const destEntityName = process.argv[4];   // e.g. Product
const destKebab = process.argv[5];        // e.g. products
const destEntityKebab = process.argv[6];  // e.g. product
const destSnake = process.argv[7];        // e.g. products
const destEntitySnake = process.argv[8];  // e.g. product
const destCamel = process.argv[9];        // e.g. products
const destEntityCamel = process.argv[10]; // e.g. product

const baseDir = "c:\\Users\\Lenovo\\Documents\\PROYECTOS\\VIDULA";

const pathsToClone = [
    { src: `src/Modules/${srcModuleName}`, dest: `src/Modules/${destModuleName}` },
    { src: `resources/js/Pages/company-data`, dest: `resources/js/Pages/${destKebab}` },
    { src: `resources/js/modules/company-data`, dest: `resources/js/modules/${destKebab}` }
];

const replacements = [
    { from: /CompanyDataId/g, to: `${destEntityName}Id` },
    { from: /CompanyData/g, to: destEntityName },
    { from: /CompanyDatas/g, to: destModuleName },
    { from: /company-data/g, to: destEntityKebab },
    { from: /company_data/g, to: destEntitySnake },
    { from: /companyData/g, to: destEntityCamel }
];

function cloneAndReplace(srcPath, destPath) {
    if (!fs.existsSync(srcPath)) return;
    
    fs.cpSync(srcPath, destPath, { recursive: true });

    function processDir(dir) {
        const items = fs.readdirSync(dir);
        for (const item of items) {
            const itemPath = path.join(dir, item);
            const stat = fs.statSync(itemPath);
            
            let newName = item;
            replacements.forEach(r => { newName = newName.replace(r.from, r.to); });
            
            const newPath = path.join(dir, newName);
            if (item !== newName) {
                fs.renameSync(itemPath, newPath);
            }
            
            if (stat.isDirectory()) {
                processDir(newPath);
            } else {
                const content = fs.readFileSync(newPath, 'utf8');
                let newContent = content;
                replacements.forEach(r => {
                    newContent = newContent.replace(r.from, r.to);
                });
                if (content !== newContent) {
                    fs.writeFileSync(newPath, newContent, 'utf8');
                }
            }
        }
    }

    processDir(destPath);
}

pathsToClone.forEach(p => {
    cloneAndReplace(path.join(baseDir, p.src), path.join(baseDir, p.dest));
});

console.log(`Cloned to ${destModuleName}`);
