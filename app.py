from flask import Flask, request, jsonify
import pickle
import numpy as np
import networkx as nx
from flask_cors import CORS

app = Flask(__name__)
CORS(app)  # This allows your frontend to access this API

# Load the model and other components
with open('model.pkl', 'rb') as f:
    model = pickle.load(f)

with open('label_encoder.pkl', 'rb') as f:
    le = pickle.load(f)

with open('multilabel_binarizer.pkl', 'rb') as f:
    mlb = pickle.load(f)

with open('symptom_graph.pkl', 'rb') as f:
    symptom_graph = pickle.load(f)

@app.route('/predict', methods=['POST'])
def predict():
    """
    Endpoint to predict diseases based on symptoms
    Expected JSON: {"symptoms": ["symptom1", "symptom2", ...], "top_n": 3}
    """
    data = request.get_json()
    symptoms = data.get('symptoms', [])
    top_n = data.get('top_n', 3)
    
    results = predict_disease(symptoms, top_n)
    return jsonify({"predictions": results})

@app.route('/next-symptom', methods=['POST'])
def next_symptom():
    """
    Endpoint to get the next symptom to ask about
    Expected JSON: {"current_symptoms": ["symptom1", "symptom2", ...]}
    """
    data = request.get_json()
    current_symptoms = data.get('current_symptoms', [])
    
    next_sym = get_next_symptom(current_symptoms)
    return jsonify({"next_symptom": next_sym})

@app.route('/all-symptoms', methods=['GET'])
def all_symptoms():
    """
    Return all possible symptoms that the model knows about
    """
    return jsonify({"symptoms": list(mlb.classes_)})

def predict_disease(symptoms, top_n=3):
    input_vector = np.zeros(len(mlb.classes_))
    for symptom in symptoms:
        if symptom in mlb.classes_:
            idx = list(mlb.classes_).index(symptom)
            input_vector[idx] = 1

    # Get probabilities
    probabilities = model.predict_proba([input_vector])[0]
    disease_indices = np.argsort(probabilities)[::-1]

    # Get top predictions
    results = []
    for idx in disease_indices[:top_n]:
        disease = le.inverse_transform([idx])[0]
        prob = probabilities[idx]
        results.append({"disease": disease, "probability": round(float(prob), 2)})

    return results

def get_next_symptom(current_symptoms):
    """Get the most relevant next symptom to ask about"""
    candidates = []

    # Find neighbors of current symptoms in graph
    for sym in current_symptoms:
        if sym in symptom_graph:  # Make sure the symptom exists in the graph
            for neighbor in symptom_graph.neighbors(sym):
                if neighbor not in current_symptoms:
                    weight = symptom_graph[sym][neighbor]['weight']
                    candidates.append((neighbor, weight))

    # Return symptom with highest co-occurrence weight
    if candidates:
        return max(candidates, key=lambda x: x[1])[0]
    return None

if __name__ == '__main__':
    app.run(debug=True, port=5000)

