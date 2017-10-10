@include('vendor/autoload')

@setup
    $dotenv = new Dotenv\Dotenv(__DIR__);
    try {
        $dotenv->load();
        $dotenv->required([
            'DEPLOY_SERVER',
            'DEPLOY_REPO',
            'DEPLOY_PATH',
        ])->notEmpty();
    } catch (Exception $e) {
        echo $e->getMessage();
    }

    $server = getenv('DEPLOY_SERVER');
    $repo = getenv('DEPLOY_REPO');
    $path = getenv('DEPLOY_PATH');

    if (substr($path, 0, 1) !== '/') throw new Exception('Careful - your deployment path does not begin with /');

    $date = (new DateTime())->format('YmdHis');
    $env = isset($env) ? $env : "production";
    $branch = isset($branch) ? $branch : "master";
    $path = rtrim($path, '/');
    $envPath = $path . '/.env';
    $releasesPath = $path . '/' . 'releases';
    $release = $releasesPath . '/' . $date;
@endsetup

@servers(['web' => $server])

@task('init')
    if [-f {{ $envPath }}]; then
        if [! -d {{ $releasesPath }}]; then
            cd {{ $path }}

            echo "Creating 'releases' directory..."
            mkdir {{ $releasesPath }}
            echo "Releases directory created!"

            echo "Cloning repo..."
            cd {{ $releasesPath }}
            git clone {{ $repo }} --branch={{ $branch }} --depth=1 -q {{ $release }}
            echo "Repo cloned!"

            echo "Setting up storage directory..."
            mv {{ $release }}/storage {{ $path }}/storage
            ln -s {{ $path }}/storage {{ $release }}/storage
            ln -s {{ $path }}/storage/public {{ $release }}/public/storage
            echo "Storage directory set up!"

            echo "Symlinking environment file..."
            ln -s {{ $path }}/.env {{ $release }}/.env
            echo "Environment file symlinked!"

            echo "Cleaning up..."
            rm -rf {{ $release }}
            echo "Deployment path ready. Run 'envoy run deploy' now!"
        else
            echo "The 'releases' directory already exist. Run 'envoy run deploy' to start your deployment."
        fi
    else
        echo "Could not find a .env file. Please make one via forge to continue."
    fi
@endtask

@story('deploy')
    release
    composer
    assets
    migrate
    cache
    optimize
    activate
    horizon_terminate
@endstory

@task('release')
    echo "Cloning new release..."
    cd {{ $releasesPath }}
    git clone {{ $repo }} --branch={{ $master }} -q {{ $release }}
    ln -s {{ $path }}/.env {{ $release }}/.env
    echo "New release cloned!"
@endtask


@task('composer')
    echo "Installing composer dependencies..."
    cd {{ $release }}
    composer install --no-interaction --quiet --no-dev
    echo "Composer dependencies installed!"
@endtask

@task('assets')
    echo "Building assets"
    cd {{ $release }}
    npm install
    npm run prod --silent
    echo "Assets built!"
@endtask

@task('migrate')
    echo "Migrating database..."
    cd {{ $release }}
    php artisan migrate --env={{ $env }} --force --no-interaction
    echo "Database migrated!"
@endtask

@task('cache')
    echo "Clearing cache..."
    cd {{ $release }}
    php artisan view:clear --quiet
    php artisan config:cache --quiet
    php artisan route:cache --quiet
    echo "Cache cleared!"
@endtask

@task('optimize')
    cd {{ $release }}
    php artisan optimize --quiet
@endtask

@task('activate')
    echo "Activating release..."
    cd {{ $path }}
    rm -rf {{ $release }}/storage
    ln -s {{ $path }}/storage {{ $release }}/storage
    ln -s {{ $path }}/storage/public {{ $release }}/public/storage
    ln -nfs {{ $release }} {{ $path }}/current
    echo "Release activated!"
@endtask

@task('horizon_terminate')
    echo "Terminating horizon daemon..."
    cd {{ $path }}/current
    php artisan horizon:terminate --quiet
    echo "Horizon daemon terminated!"
@endtask
