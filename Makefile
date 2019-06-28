DEPLOYER=artifactory.wikia-inc.com/ops/k8s-deployer:latest

# Build Docker image
build:
	docker build -t artifactory.wikia-inc.com/aden/dfp-query-tool:latest -f k8s/Dockerfile .

# List available commands
list_commands:
	docker run --rm -v `pwd`/src/:/home/maka/dfp-query-tool/src/ artifactory.wikia-inc.com/aden/dfp-query-tool app/console
