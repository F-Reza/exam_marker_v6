param([string]$Name = 'exam-marker-v6.zip')
$ErrorActionPreference='Stop'
if([System.IO.Path]::GetFileName($Name) -ne $Name -or !$Name.EndsWith('.zip')){throw 'Use a ZIP filename without directory components.'}
$releaseRoot=[System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$outputDirectory=Join-Path $releaseRoot 'output/releases'
[System.IO.Directory]::CreateDirectory($outputDirectory) | Out-Null
$outputFile=Join-Path $outputDirectory $Name
if(Test-Path -LiteralPath $outputFile){throw 'Release already exists. Choose a new name.'}
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$folders=@('app','bootstrap','config','database/migrations','database/seeders','resources','routes','scripts','tests','public')
$rootFiles=@('artisan','composer.json','composer.lock','package.json','package-lock.json','vite.config.js','phpunit.xml','README.md','.env.example','.gitignore')
$files=[System.Collections.Generic.List[string]]::new()
function Add-SourceDirectory([string]$directory){
 foreach($item in Get-ChildItem -LiteralPath $directory -Force){
  if($item.Attributes -band [System.IO.FileAttributes]::ReparsePoint){continue}
  $relative=$item.FullName.Substring($releaseRoot.Length+1).Replace('\','/')
  if($relative -match '(^|/)(\.git|node_modules|vendor|storage|cache|pdf-qa)(/|$)' -or $relative -match '\.(sqlite|sql|log)(-|$)'){continue}
  if($item.PSIsContainer){Add-SourceDirectory $item.FullName}else{$files.Add($item.FullName)}
 }
}
foreach($folder in $folders){$directory=Join-Path $releaseRoot $folder;if(Test-Path -LiteralPath $directory){Add-SourceDirectory $directory}}
foreach($relative in $rootFiles){$file=Join-Path $releaseRoot $relative;if(Test-Path -LiteralPath $file){$files.Add($file)}}
foreach($file in Get-ChildItem -LiteralPath (Join-Path $releaseRoot 'docs') -Filter '*.md' -File){$files.Add($file.FullName)}
$stream=[System.IO.File]::Open($outputFile,[System.IO.FileMode]::CreateNew)
$zip=[System.IO.Compression.ZipArchive]::new($stream,[System.IO.Compression.ZipArchiveMode]::Create)
try{
 foreach($file in ($files | Sort-Object -Unique)){
  $relative=$file.Substring($releaseRoot.Length+1).Replace('\','/')
  if($relative -match '(^|/)\.env' -and $relative -notin @('.env.example','tests/.env.testing')){continue}
  if($relative -eq '.env.example'){
   $entry=$zip.CreateEntry($relative);$writer=[System.IO.StreamWriter]::new($entry.Open())
   try{foreach($line in [System.IO.File]::ReadAllLines($file)){$writer.WriteLine(($line -replace '^([A-Z0-9_]*(KEY|PASSWORD|SECRET|TOKEN|CREDENTIAL|SALT|AUTHORIZATION)[A-Z0-9_]*)=.*$','$1='))}}finally{$writer.Dispose()}
  }else{[System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip,$file,$relative) | Out-Null}
 }
}finally{$zip.Dispose();$stream.Dispose()}
$check=[System.IO.Compression.ZipFile]::OpenRead($outputFile)
try{
 $bad=$check.Entries | Where-Object { $_.FullName -match '(^|/)(\.git|vendor|node_modules|storage)(/|$)|\.(sqlite|sql|log)(-|$)' -or ($_.FullName -match '(^|/)\.env' -and $_.FullName -notin @('.env.example','tests/.env.testing')) }
 if($bad){throw 'Release validation found a forbidden entry.'}
 'Verified source release: '+$outputFile
 'Entries: '+$check.Entries.Count
 'SHA256: '+(Get-FileHash -LiteralPath $outputFile -Algorithm SHA256).Hash
}finally{$check.Dispose()}
