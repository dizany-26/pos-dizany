Option Explicit

Dim shell
Set shell = CreateObject("WScript.Shell")

shell.Run "powershell.exe -NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File ""C:\xampp\htdocs\dizany\scripts\start-local-server.ps1""", 0, False
