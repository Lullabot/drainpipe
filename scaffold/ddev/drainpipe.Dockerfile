# Install jq
RUN apt-get update && apt-get install -y jq && apt-get clean

# Install Task
RUN sh -c "$(curl -fsSL https://taskfile.dev/install.sh)" -- -d $(jq -r '.extra.drainpipe.taskfile' composer.json) -b /usr/local/bin

# Install action-validator
RUN yarn global add @action-validator/core @action-validator/cli
