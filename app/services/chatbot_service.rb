class ChatbotService
  def initialize(user_message)
    @user_message = user_message
  end

  def generate_response
    client = OpenAI::Client.new(access_token: Rails.application.credentials.dig(:openai, :api_key))

    response = client.chat(
      parameters: {
        model: "gpt-4o-mini", # or "gpt-3.5-turbo"
        messages: [
          { role: "system", content: "You are a friendly customer support assistant." },
          { role: "user", content: @user_message }
        ],
        temperature: 0.7
      }
    )

    response.dig("choices", 0, "message", "content") || "Sorry, I couldn’t understand that."
  rescue => e
    Rails.logger.error("Chatbot error: #{e.message}")
    "I’m having trouble responding right now."
  end
end