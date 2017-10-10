# Laravel Envoy Deployment Script

In order to get started copy the Envoy.blade.php file to your local project folder. Set the required environment variables and run `envoy run init`. This will make the necessary changes to your project folder.

## Deployments

After haven run `envoy run init`, you can start your deployment by running `envoy run deploy`.

## Required environment variables
```
DEPLOY_SERVER=forge@your-server-ip
DEPLOY_REPO=git@url
DEPLOY_PATH=/home/forge/example.com
```
