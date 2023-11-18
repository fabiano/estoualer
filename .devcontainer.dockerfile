FROM buildpack-deps:bookworm
ENV DOTNET_GENERATE_ASPNET_CERTIFICATE false
ENV DOTNET_NOLOGO true
ENV DOTNET_ROOT /opt/dotnet
ENV DOTNET_SDK_VERSION 8.0.100
ENV DOTNET_USE_POLLING_FILE_WATCHER true
ENV USER_GID 1000
ENV USER_UID 1000
ENV USER_NAME nonroot
ENV PATH ${PATH}:${DOTNET_ROOT}
ENV PATH ${PATH}:${DOTNET_ROOT}/tools
RUN export DEBIAN_FRONTEND=noninteractive && \
    apt-get update && \
    apt-get install -yq bash-completion bat exa fzf && \
    curl -sS https://starship.rs/install.sh | sh -s -- -y
RUN mkdir -p ${DOTNET_ROOT} && \
    curl -fsSLO https://dotnetcli.azureedge.net/dotnet/Sdk/${DOTNET_SDK_VERSION}/dotnet-sdk-${DOTNET_SDK_VERSION}-linux-x64.tar.gz && \
    tar -xzf dotnet-sdk-${DOTNET_SDK_VERSION}-linux-x64.tar.gz -C ${DOTNET_ROOT} --no-same-owner && \
    rm dotnet-sdk-${DOTNET_SDK_VERSION}-linux-x64.tar.gz && \
    dotnet help
RUN addgroup --gid ${USER_GID} ${USER_NAME} && \
    adduser --uid ${USER_UID} --gid ${USER_GID} --shell /bin/bash ${USER_NAME}
COPY .devcontainer.bashrc /home/${USER_NAME}/.bashrc
COPY .devcontainer.starship /home/${USER_NAME}/.config/starship.toml
RUN chgrp -R ${USER_GID} /home/${USER_NAME} && \
    chown -R ${USER_UID} /home/${USER_NAME}
RUN mkdir /src && \
    chgrp -R ${USER_GID} /src && \
    chown -R ${USER_UID} /src
USER ${USER_NAME}
