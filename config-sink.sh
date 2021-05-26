for i in ./config/install/*.yml; do 
  yq d -i $i uuid
  yq d -i $i _core
done
