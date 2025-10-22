class ChatbotService
  def initialize(user_message)
    @user_message = user_message
  end

  def generate_response
    if @user_message.downcase.include?("hello")
      "Hi there! How can I help you today?"
    else
      "I'm not sure yet, but I'm learning!"
    end
  end
end