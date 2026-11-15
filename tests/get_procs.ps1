Get-CimInstance Win32_Process -Filter "name='php.exe'" | Select-Object ProcessId, CommandLine | Format-Table -Wrap
