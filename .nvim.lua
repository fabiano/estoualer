-- disable K mapping
vim.g["conjure#mapping#doc_word"] = false

vim.lsp.config('clojure-lsp', {
  cmd = { 'clojure-lsp' },
  filetypes = { 'clojure', 'edn' },
  root_markers = { 'project.clj', 'deps.edn', 'build.boot', 'shadow-cljs.edn', '.git', 'bb.edn' },
})

vim.lsp.enable('clojure-lsp')

local MiniDeps = require('mini.deps')

MiniDeps.setup()
MiniDeps.add('Olical/conjure')

