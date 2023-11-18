# bash-completions
source /etc/profile.d/bash_completion.sh

# alias
alias ..="cd .."
alias ~="cd ~"
alias ls="exa"
alias ll="exa --long --header --git"
alias cat="batcat"
alias g="git"
alias d="dotnet"

# prompt
PS1="\n\w\n\033[0;35m❯❯\033[0m "

# start starship
eval "$(starship init bash)"
