#!/usr/bin/env bash
set -e

APP_PATH="${APP_PATH}"
UID=${UID}
GID=${GID}

if [ ! -f "$APP_PATH/artisan" ]; then
    echo "Could not find Laravel application at $APP_PATH"
    exit 1
fi

if grep -q "^APP_KEY=$" "$APP_PATH/.env" || grep -q "^APP_KEY=base64:$" "$APP_PATH/.env"; then
    gosu ${UID}:${GID} php "$APP_PATH/artisan" key:generate
fi

gosu ${UID}:${GID} php "$APP_PATH/artisan" reset:database || true

gosu ${UID}:${GID} php "$APP_PATH/artisan" tinker --execute='
try {
    $bucketName = getenv("AWS_BUCKET");
    $endpoint = getenv("AWS_ENDPOINT");

    $s3Client = new Aws\S3\S3Client([
        "version" => "latest",
        "region"  => getenv("AWS_DEFAULT_REGION"),
        "endpoint" => $endpoint,
        "use_path_style_endpoint" => true,
        "credentials" => [
            "key"    => getenv("AWS_ACCESS_KEY_ID"),
            "secret" => getenv("AWS_SECRET_ACCESS_KEY"),
        ]
    ]);

    if (!$s3Client->doesBucketExist($bucketName)) {
        $s3Client->createBucket(["Bucket" => $bucketName]);
    }
} catch (Exception $e) {
    echo "Error creating bucket: " . $e->getMessage() . PHP_EOL;
}
'

if [ -f "$APP_PATH/storage/oauth-private.key" ]; then
    chmod 600 "$APP_PATH/storage/oauth-private.key"
fi

if [ -f "$APP_PATH/storage/oauth-public.key" ]; then
    chmod 600 "$APP_PATH/storage/oauth-public.key"
fi

sed -i 's/autostart=false/autostart=true/g' /etc/supervisor/conf.d/laravel-worker.conf

supervisorctl reread
supervisorctl update

supervisorctl start laravel-worker:*
