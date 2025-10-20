# Install Task
RUN sh -c "$(curl -fsSL https://taskfile.dev/install.sh)" -- -d $(cat .taskfile) -b /usr/local/bin

# Install action-validator
RUN yarn global add @action-validator/core @action-validator/cli
