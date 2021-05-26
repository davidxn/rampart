import shutil
import os

shutil.make_archive('/tmpramp/RAMP-SNAPSHOT', 'zip', './work/pk3/')
os.rename('/tmpramp/RAMP-SNAPSHOT.zip','/tmpramp/RAMP-SNAPSHOT.pk3')
