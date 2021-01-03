const readstream = require('./lib/readstream');
const { play } = require('./lib/playwright');

(async () => {
  // stdin から payload 読み込み
  const payload = JSON.parse(await readstream.stringify(process.stdin))
  if (payload === null) {
    return false
  }
  const result = await play(payload)
  if (result) {
    console.log(JSON.stringify(result))
  }
})()