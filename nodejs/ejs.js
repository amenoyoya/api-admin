const ejs = require('ejs')
const readstream = require('./lib/readstream')

const main = async () => {
  // stdin から payload 読み込み
  const payload = JSON.parse(await readstream.stringify(process.stdin))
  if (payload === null) {
    return false
  }

  // コマンドライン引数をテンプレートとして使用
  const template = process.argv[2]
  if (template === null) {
    return false
  }

  // ejs でテンプレート展開
  console.log(ejs.render(template, {payload}))
}

main()