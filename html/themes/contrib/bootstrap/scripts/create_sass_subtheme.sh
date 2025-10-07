#!/bin/bash
# Script to quickly create sub-theme.

echo '
+------------------------------------------------------------------------+
| With this script you could quickly create bootstrap sub-theme             |
| In order to use this:                                                  |
| - bootstrap theme (this folder) should be in the contrib folder |
+------------------------------------------------------------------------+
'
echo 'Your theme name ? [e.g. My custom bootstrap]'
read CUSTOM_BOOTSTRAP_NAME

echo 'The machine name of your custom theme? [e.g. mycustom_bootstrap]'
read CUSTOM_BOOTSTRAP

if [[ ! -e ../../custom ]]; then
    mkdir ../../custom
fi
cp -r subthemes/bootstrap_sass ../../custom/$CUSTOM_BOOTSTRAP
cd ../../custom/$CUSTOM_BOOTSTRAP
for file in *bootstrap_sass.*; do mv $file ${file//bootstrap_sass/$CUSTOM_BOOTSTRAP}; done
for file in config/*/*bootstrap_sass*.*; do mv $file ${file//bootstrap_sass/$CUSTOM_BOOTSTRAP}; done
mv $CUSTOM_BOOTSTRAP.theme ${file//bootstrap_sass/$CUSTOM_BOOTSTRAP}
if [[ "$OSTYPE" == "darwin"* ]]; then
  grep -Rl bootstrap_sass .|xargs sed -i '' -e "s/bootstrap_sass/$CUSTOM_BOOTSTRAP/"
else
  grep -Rl bootstrap_sass .|xargs sed -i -e "s/bootstrap_sass/$CUSTOM_BOOTSTRAP/"
fi
sed -i -e "s/SASS Bootstrap Starter Kit Subtheme/$CUSTOM_BOOTSTRAP_NAME/" $CUSTOM_BOOTSTRAP.info.yml
echo "# Check the themes/custom folder for your new sub-theme."
