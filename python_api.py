from flask import Flask, request, jsonify

app = Flask(__name__)


@app.route('/')
def process_text():
    # Dummy processing function
    return 'Hello, World!'

if __name__ == "__main__":
    app.run(debug=True)

