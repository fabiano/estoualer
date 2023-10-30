FROM buildpack-deps:bookworm
ENV USER_GID 1000
ENV USER_UID 1000
ENV USER_NAME nonroot
ENV GOPATH /home/${USER_NAME}/go
ENV GOVERSION 1.21.1
ENV NODEPATH /home/${USER_NAME}/node
ENV NODEVERSION 20.6.1
ENV PATH ${GOPATH}/bin:${NODEPATH}/bin:${PATH}
RUN export DEBIAN_FRONTEND=noninteractive && \
    echo "deb [signed-by=/usr/share/keyrings/cloud.google.gpg] http://packages.cloud.google.com/apt cloud-sdk main" | tee -a /etc/apt/sources.list.d/google-cloud-sdk.list && \
    curl https://packages.cloud.google.com/apt/doc/apt-key.gpg | apt-key --keyring /usr/share/keyrings/cloud.google.gpg add - && \
    apt-get update && \
    apt-get install -yq bash-completion bat exa fzf google-cloud-cli && \
    curl -sS https://starship.rs/install.sh | sh -s -- -y
RUN mkdir -p ${GOPATH} && \
    curl -fsSLO https://go.dev/dl/go${GOVERSION}.linux-amd64.tar.gz && \
    tar -xzf go${GOVERSION}.linux-amd64.tar.gz -C ${GOPATH} --strip-components 1 --no-same-owner && \
    rm go${GOVERSION}.linux-amd64.tar.gz
RUN curl -sSfL https://raw.githubusercontent.com/golangci/golangci-lint/master/install.sh | sh -s -- -b $(go env GOPATH)/bin latest
RUN curl -sSfL https://raw.githubusercontent.com/cosmtrek/air/master/install.sh | sh -s -- -b $(go env GOPATH)/bin latest
RUN mkdir -p ${NODEPATH} && \
    curl -fsSLO https://nodejs.org/dist/v${NODEVERSION}/node-v${NODEVERSION}-linux-x64.tar.gz && \
    tar -xzf node-v${NODEVERSION}-linux-x64.tar.gz -C ${NODEPATH} --strip-components 1 --no-same-owner && \
    rm node-v${NODEVERSION}-linux-x64.tar.gz
RUN addgroup --gid ${USER_GID} ${USER_NAME} && \
    adduser --uid ${USER_UID} --gid ${USER_GID} --shell /bin/bash ${USER_NAME}
COPY dotfiles-bashrc /home/${USER_NAME}/.bashrc
COPY dotfiles-starship /home/${USER_NAME}/.config/starship.toml
RUN chgrp -R ${USER_GID} /home/${USER_NAME} && \
    chown -R ${USER_UID} /home/${USER_NAME}
RUN mkdir /src && \
    chgrp -R ${USER_GID} /src && \
    chown -R ${USER_UID} /src
USER ${USER_NAME}
