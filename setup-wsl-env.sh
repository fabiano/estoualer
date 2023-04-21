# install gcc
sudo apt-get update
sudo apt-get upgrade -y
sudo apt-get install gcc -y

# install go
curl -LO https://go.dev/dl/go1.20.3.linux-amd64.tar.gz
sudo rm -rf /usr/local/go
sudo tar -C /usr/local -xzf go1.20.3.linux-amd64.tar.gz
rm go1.20.3.linux-amd64.tar.gz

# add /usr/local/go/bin to path
export PATH=/usr/local/go/bin:$PATH

# install golangci
sudo curl -sSfL https://raw.githubusercontent.com/golangci/golangci-lint/master/install.sh | sh -s -- -b $(go env GOPATH)/bin latest
