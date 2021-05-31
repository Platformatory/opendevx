#!/bin/bash
echo "Now copying over config from your project install to the profile config..."
cp -R ../../../../config/sync/* ./config/install/
echo "Now scrubbing uuid and _core default config hash. This is a silly script and takes a whiile..."
for i in ./config/install/*.yml; do 
  yq d -i $i uuid
  yq d -i $i _core
done
echo "Config has been scrubbed. Make sure you keep your eyes open and do a git diff and actually try reimporting the config.."
