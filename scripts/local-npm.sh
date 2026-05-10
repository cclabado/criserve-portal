#!/bin/zsh

set -euo pipefail

export MAMBA_ROOT_PREFIX="${HOME}/.local/micromamba/root"
MICROMAMBA="${HOME}/.local/micromamba/bin/micromamba"

exec "${MICROMAMBA}" run -n criserve-env npm "$@"
