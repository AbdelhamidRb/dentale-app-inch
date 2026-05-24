Dim ps
ps = "C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe"
CreateObject("WScript.Shell").Run ps & " -Sta -ExecutionPolicy Bypass -File ""C:\laragon\www\dental-app-inch\scripts\launcher.ps1""", 0, False
