# This is just to copy into working repos to shortcut exports since I can't get pre-commit hooks working

addonId='addonIdHere'
directory='directoryYouAreDevelopingIn'

dataPath='data' 			# path to data folder from the current location
templatesPath='templates' # path to templates folder from the current location

workingDirectory=${PWD}
cd ${directory}
xf export ${addonId} ${workingDirectory}
cd ${workingDirectory}
git add ${dataPath}
git add ${templatesPath}