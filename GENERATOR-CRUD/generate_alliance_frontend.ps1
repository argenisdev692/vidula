$srcDir = "c:\Users\Lenovo\Documents\PROYECTOS\AQUASHIELD-CRM\resources\js\Pages\insurance-companies"
$destDir = "c:\Users\Lenovo\Documents\PROYECTOS\AQUASHIELD-CRM\resources\js\Pages\alliance-companies"

if (Test-Path $destDir) {
    Remove-Item -Recurse -Force $destDir
}
Copy-Item -Path $srcDir -Destination $destDir -Recurse

function Rename-Recursive($path) {
    $items = Get-ChildItem -Path $path | Sort-Object -Property @{Expression={$_.FullName.Length}; Descending=$true}
    foreach ($item in $items) {
        $newName = $item.Name -replace 'Insurance', 'Alliance'
        $newName = $newName -replace 'insurance', 'alliance'
        if ($newName -cne $item.Name) {
            Rename-Item -Path $item.FullName -NewName $newName
        }
    }
    
    $items = Get-ChildItem -Path $path
    foreach ($item in $items) {
        if ($item.PSIsContainer) {
            Rename-Recursive -path $item.FullName
        }
    }
}
Rename-Recursive -path $destDir

$files = Get-ChildItem -Path $destDir -Recurse -File
foreach ($file in $files) {
    $content = Get-Content -Path $file.FullName -Raw
    $newContent = $content -replace 'InsuranceCompany', 'AllianceCompany'
    $newContent = $newContent -replace 'InsuranceCompanies', 'AllianceCompanies'
    $newContent = $newContent -replace 'insurance_company', 'alliance_company'
    $newContent = $newContent -replace 'insurance_companies', 'alliance_companies'
    $newContent = $newContent -replace 'insurance-company', 'alliance-company'
    $newContent = $newContent -replace 'insurance-companies', 'alliance-companies'
    $newContent = $newContent -replace 'Insurance', 'Alliance'
    $newContent = $newContent -replace 'insurance', 'alliance'
    if ($content -cne $newContent) {
        [System.IO.File]::WriteAllText($file.FullName, $newContent)
    }
}
Write-Host "Done templating alliance-companies frontend"
