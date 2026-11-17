for dep in billing-api billing-worker production-api production-worker; do
  kubectl exec -n saga-oficina deploy/$dep -- sh -c '
    touch .env
    tr -d "\r" < .env > .env.tmp && mv .env.tmp .env
    printf "\n" >> .env
    for var in APP_ENV APP_DEBUG APP_KEY DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD MONGODB_URI MONGODB_DATABASE RABBITMQ_HOST RABBITMQ_PORT RABBITMQ_VHOST RABBITMQ_USER RABBITMQ_PASSWORD; do
      val=$(printenv "$var" | tr -d "\r")
      if [ -n "$val" ]; then
        sed -i "/^${var}=/d" .env
        printf "%s=%s\n" "$var" "$val" >> .env
      fi
    done
    php artisan config:clear
  '
done