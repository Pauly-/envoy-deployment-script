@include('vendor/autoload')

@setup
    $dotenv = new Dotenv\Dotenv(__DIR__);
    try {
        $dotenv->load();
        $dotenv->required([
            'DEPLOY_SERVER',
            'DEPLOY_REPO',
            'DEPLOY_PATH'
        ])->notEmpty();
    } catch (Exception $e) {
        echo $e->getMessage();
    }

    $server = getenv('DEPLOY_SERVER');
    $repo = getenv('DEPLOY_REPOSITORY');
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
        else
            echo "Releases directory does already exist. Run 'envoy run deploy' to start your deployment."
        fi
    else
        echo "Could not find a .env file. Please make one via forge to continue."
    fi
@endtask
