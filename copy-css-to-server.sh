#!/bin/bash

# Copy compiled CSS to server
echo "Copying updated CSS to server..."

# Use scp to copy the compiled CSS file
scp -i ~/.ssh/globaleadershipacademy01_rsa public/build/assets/app-8e2071a4.css globalea@66.45.244.170:~/temp-app.css

# Then SSH in and move it to the correct location
ssh -i ~/.ssh/globaleadershipacademy01_rsa globalea@66.45.244.170 "cp ~/temp-app.css ~/leadership/public/build/assets/app.css && rm ~/temp-app.css && echo 'CSS updated successfully!'"

echo "Navigation styling deployed to live site!"
