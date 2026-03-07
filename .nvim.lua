vim.lsp.config('intelephense', {
  cmd = { 'npx', 'intelephense', '--stdio' },
})

vim.lsp.enable('intelephense')

