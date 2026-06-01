Dim ps, script
script = WScript.Arguments(0)
ps = "powershell.exe -NoProfile -ExecutionPolicy Bypass -File """ & script & """"
CreateObject("WScript.Shell").Run ps, 0, True