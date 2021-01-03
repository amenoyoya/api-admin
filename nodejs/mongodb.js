const readstream = require('./lib/readstream')
const { connect, execute } = require('./lib/mongodb')

require('dotenv').config()

// 接続先URL
const url = (process.env.MONGO_USER && process.env.MONGO_PASSWORD)?
  `mongodb://${process.env.MONGO_USER}:${process.env.MONGO_PASSWORD}@${process.env.MONGO_HOST}:${process.env.MONGO_PORT}`:
  `mongodb://${process.env.MONGO_HOST}:${process.env.MONGO_PORT}`

const main = async () => {
  /**
   * stdin から MongoDB 読み込み設定を読み込む
   */
  const payload = JSON.parse(await readstream.stringify(process.stdin))
  if (payload === null) {
    return false
  }

  let client
  try {
    client = await connect(url)
    console.log(await execute(client, payload))
    client.close()
  } catch (err) {
    console.error(err)
    client.close()
  }
}

main()