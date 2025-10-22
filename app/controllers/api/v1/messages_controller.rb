class Api::V1::MessagesController < ApplicationController

  def create
    message = Message.create!(
      user: params[:user_message],
      session_id: params[:session_id]
    )

    bot_reply = ChatbotService.new(message.user).generate_response
    message.update(bot: bot_reply)

    render json: { reply: bot_reply }
  end
end
