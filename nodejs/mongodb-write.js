const readstream = require('./lib/readstream')
const { MongoClient } = require('mongodb')
const assert = require('assert')

require('dotenv').config()

// 接続先URL
const url = (process.env.MONGO_USER && process.env.MONGO_PASSWORD)?
  `mongodb://${process.env.MONGO_USER}:${process.env.MONGO_PASSWORD}@${process.env.MONGO_HOST}:${process.env.MONGO_PORT}`:
  `mongodb://${process.env.MONGO_HOST}:${process.env.MONGO_PORT}`

/**
 * 追加オプション
 * MongoClient用オプション設定
 */
const connectOption = {
  useNewUrlParser: true,
  useUnifiedTopology: true,
}

const main = async () => {
  /**
   * stdin から MongoDB 更新設定を読み込む
   */
  const config = JSON.parse(await readstream.stringify(process.stdin))
  if (config === null) {
    return false
  }

  /**
   * データベース接続
   * データベース接続用の引数追加
   */
  MongoClient.connect(url, connectOption, async (err, client) => {
    if (err !== null) {
      return false
    }
    try {
      const db = client.db(config.database)
      const collection = db.collection(config.collection)
      
      if (typeof config.insert === 'object') {
        if (Array.isArray(config.insert)) {
          const result = await collection.insertMany(config.insert)
          if (result.insertedCount !== config.insert.length) {
            return false
          }
        } else {
          const result = await collection.insertOne(config.insert)
          if (result.insertedCount !== 1) {
            return false
          }
        }
      }
      if (Array.isArray(config.update)) {
        await collection.updateMany(...config.update)
      }
      if (typeof config.delete === 'object') {
        await collection.deleteMany(config.delete)
      }
      client.close()
    } catch (err) {
      console.log(err)
      client.close()
    }
  })
}

main()