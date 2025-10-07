param(
  [Parameter(Mandatory=$true)][string]$Script,
  [Parameter(Mandatory=$true)][string]$Log
)
$rman = "rman.exe"
if (-not (Get-Command $rman -ErrorAction SilentlyContinue)) {
  Write-Error "rman.exe not found"
  exit 127
}
& $rman "cmdfile=$Script" "log=$Log"
exit $LASTEXITCODE
