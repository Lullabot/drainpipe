# Install Task
ADD taskfile .
RUN sh -c "$(curl -fsSL https://taskfile.dev/install.sh)" -- -b /usr/local/bin -d $(cat taskfile)

# Install action-validator
RUN yarn global add @action-validator/core @action-validator/cli
