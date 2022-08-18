del "%~dp0LibraryPortable.exe"
del "%~dp0LibraryInstaller.exe"
RMDIR /S /Q "%~dp0build"


pyinstaller --noconfirm --onefile --windowed --icon "%~dp0S-for-SIM.ico" --name "LibraryPortable" --add-data "%~dp0../library.sql;." --add-data "%~dp0../pictures;pictures/"  "%~dp0../source/library.py" --distpath "%~dp0/"
"C:\Program Files (x86)\Inno Setup 6\iscc.exe" "%~dp0build_exe.iss"