Dim ps, scriptDir
ps         = "C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe"
scriptDir  = CreateObject("Scripting.FileSystemObject").GetFile(WScript.ScriptFullName).ParentFolder.Path
CreateObject("WScript.Shell").Run ps & " -Sta -ExecutionPolicy Bypass -File """ & scriptDir & "\launcher.ps1""", 0, False
