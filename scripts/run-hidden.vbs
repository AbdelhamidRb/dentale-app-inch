CreateObject("WScript.Shell").Run "powershell.exe -NonInteractive -NoProfile -ExecutionPolicy Bypass -File """ & WScript.Arguments(0) & """", 0, False
