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

@app.route('/process-symptom', methods=['POST'])
def process_symptom():
    """
    New endpoint to process a symptom interaction
    Expected JSON: 
    {
        "action": "initial" | "response",
        "current_symptoms": ["symptom1", "symptom2", ...],
        "input_symptom": "new symptom" (if action is initial),
        "response": "yes" | "no" | "stop" (if action is response),
        "current_question": "symptom being asked" (if action is response)
    }
    """
    data = request.get_json()
    action = data.get('action', '')
    current_symptoms = data.get('current_symptoms', [])
    
    if action == 'initial':
        # Process initial symptom
        input_symptom = data.get('input_symptom', '').strip()
        # Capitalize first letter for consistency
        input_symptom = input_symptom.capitalize()
        
        # Check if symptom is valid
        if input_symptom in mlb.classes_:
            response = {
                "status": "success",
                "message": f"Do you have {input_symptom}?",
                "valid_symptom": True,
                "symptom": input_symptom
            }
        else:
            # Try to find a close match
            all_symptoms = list(mlb.classes_)
            matches = [s for s in all_symptoms if input_symptom.lower() in s.lower()]
            if matches:
                response = {
                    "status": "partial_match",
                    "message": f"Did you mean {matches[0]}?",
                    "suggestions": matches[:3],
                    "valid_symptom": False
                }
            else:
                response = {
                    "status": "error",
                    "message": "I don't recognize that symptom. Please try another one.",
                    "valid_symptom": False
                }
    
    elif action == 'response':
        user_response = data.get('response', '').lower()
        current_question = data.get('current_question', '')
        asked_symptoms = data.get('asked_symptoms', [])
        
        if user_response == 'yes':
            # Add the symptom to current symptoms if not already there
            if current_question not in current_symptoms:
                current_symptoms.append(current_question)
            
            # Add to asked symptoms set
            if current_question not in asked_symptoms:
                asked_symptoms.append(current_question)
            
            # Get predictions
            predictions = predict_disease(current_symptoms)
            
            # Check confidence
            high_confidence = predictions and predictions[0]['probability'] > 0.9
            
            if high_confidence:
                response = {
                    "status": "complete",
                    "message": "High confidence diagnosis reached!",
                    "current_symptoms": current_symptoms,
                    "predictions": predictions,
                    "asked_symptoms": asked_symptoms
                }
            else:
                # Get next symptom
                next_sym = get_next_symptom_with_filter(current_symptoms, asked_symptoms)
                
                if next_sym:
                    response = {
                        "status": "continue",
                        "message": f"Do you have {next_sym}?",
                        "current_symptoms": current_symptoms,
                        "predictions": predictions,
                        "next_symptom": next_sym,
                        "asked_symptoms": asked_symptoms
                    }
                else:
                    response = {
                        "status": "complete",
                        "message": "No more relevant symptoms to check.",
                        "current_symptoms": current_symptoms,
                        "predictions": predictions,
                        "asked_symptoms": asked_symptoms
                    }
        
        elif user_response == 'no':
            # Add to asked symptoms set
            if current_question not in asked_symptoms:
                asked_symptoms.append(current_question)
            
            # Get next symptom
            next_sym = get_next_symptom_with_filter(current_symptoms, asked_symptoms)
            
            if next_sym:
                response = {
                    "status": "continue",
                    "message": f"Do you have {next_sym}?",
                    "current_symptoms": current_symptoms,
                    "next_symptom": next_sym,
                    "asked_symptoms": asked_symptoms
                }
            else:
                # Get final predictions
                predictions = predict_disease(current_symptoms)
                response = {
                    "status": "complete",
                    "message": "No more relevant symptoms to check.",
                    "current_symptoms": current_symptoms,
                    "predictions": predictions,
                    "asked_symptoms": asked_symptoms
                }
        
        elif user_response == 'stop':
            # Get final predictions
            predictions = predict_disease(current_symptoms)
            response = {
                "status": "complete",
                "message": "Based on your symptoms, here are the possible conditions:",
                "current_symptoms": current_symptoms,
                "predictions": predictions,
                "asked_symptoms": asked_symptoms
            }
        
        else:
            response = {
                "status": "error",
                "message": "Please answer with yes, no, or stop.",
                "current_symptoms": current_symptoms,
                "current_question": current_question,
                "asked_symptoms": asked_symptoms
            }
    
    else:
        response = {
            "status": "error",
            "message": "Invalid action. Use 'initial' or 'response'."
        }
    
    return jsonify(response)

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

def get_next_symptom_with_filter(current_symptoms, asked_symptoms):
    """Get next symptom excluding those already asked"""
    candidates = []

    # Find neighbors of current symptoms in graph
    for sym in current_symptoms:
        if sym in symptom_graph:  # Make sure the symptom exists in the graph
            for neighbor in symptom_graph.neighbors(sym):
                if neighbor not in current_symptoms and neighbor not in asked_symptoms:
                    weight = symptom_graph[sym][neighbor]['weight']
                    candidates.append((neighbor, weight))

    # Return symptom with highest co-occurrence weight
    if candidates:
        return max(candidates, key=lambda x: x[1])[0]
    return None

if __name__ == '__main__':
    app.run(debug=True, port=5000)