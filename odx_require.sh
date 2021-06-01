#!/bin/bash
if [[ $# -eq 0 ]] ; then
    echo 'Needs arguments ans also needs yq installed;  Usage: ./odx_require drupal/somemoduule'
    exit 0
fi

arg1=$1
package_name=${arg1##*/}
echo "adding $1 to composer.json.."
composer-2 require --no-update $1
echo "adding $package_name to the info.yml. This script assumed it is a module. Please verify via your git diff.."
yq w -i opendevx.info.yml "install[+]" "${package_name}"
echo "Package has been sourced. If nothing looks bad, you should commit this. Now to verify it works, you should run composer install or update from within your project that this profile is in the source of. Make sure you're getting the latest changes."

