# Install Task
RUN sh -c "$(curl -fsSL https://taskfile.dev/install.sh)" -- -d -b /usr/local/bin

# Install action-validator
yarn global add @action-validator/core @action-validator/cli
