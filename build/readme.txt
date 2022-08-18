build with auto-py-to-exe or

pyinstaller --noconfirm --onefile --windowed --icon "D:/library/build/S-for-SIM.ico" --name "LibraryPortable" --add-data "D:/library/source/library.sql;." --add-data "D:/library/source/pictures;pictures/"  "D:/library/source/library.py" --distpath "D:/library/build"