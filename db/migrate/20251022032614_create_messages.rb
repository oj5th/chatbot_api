class CreateMessages < ActiveRecord::Migration[7.1]
  def change
    create_table :messages do |t|
      t.text :user
      t.text :bot
      t.string :session_id

      t.timestamps
    end
  end
end
