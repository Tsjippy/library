[Setup]
AppName=Library
AppVersion=1.0
WizardStyle=modern
DefaultDirName={autopf}\Library
DefaultGroupName=Library
UninstallDisplayIcon={app}\Library.exe
Compression=lzma2
SolidCompression=yes
OutputBaseFilename=LibraryInstaller
OutputDir=.
SetupIconFile="S-for-SIM.ico"

[Files]
Source: "LibraryPortable.exe"; DestDir: "{app}"

[Icons]
Name: "{group}\Library"; Filename: "{app}\Library.exe"
Name: "{commondesktop}\Library"; Filename: "{app}\LibraryPortable.exe"

[Dirs]
Name: "{app}"; Permissions: users-full

[UninstallDelete]
Type: files; Name: "{app}\database.sqlite3"
Type: files; Name: "{commondesktop}\Shortcut Name.lnk"
