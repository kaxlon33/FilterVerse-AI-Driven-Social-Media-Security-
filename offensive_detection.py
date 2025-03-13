import joblib
import nltk
import re
import sys
from nltk.corpus import stopwords
from nltk.tokenize import word_tokenize
from fuzzywuzzy import fuzz

# Download NLTK resources if not already downloaded
nltk.download('stopwords')
nltk.download('punkt')

# Load the trained model and TF-IDF vectorizer
model = joblib.load('offensive_language_model.pkl')
tfidf = joblib.load('tfidf_vectorizer.pkl')

# List of explicit offensive words (add more if needed)
offensive_words = {"fuck", "bitch", "asshole", "bastard", "dumbass", "shit", "slut", "whore", "motherfucker", "mother fucker"}

# Function to clean text
def clean_text(text):
    text = re.sub(r"http\S+|www\S+|https\S+", '', text, flags=re.MULTILINE)
    text = re.sub(r'\@\w+|\#', '', text)
    text = re.sub(r'[^\w\s]', '', text)
    return text.lower()

# Function to remove stopwords
def remove_stopwords(text):
    stop_words = set(stopwords.words('english'))
    tokens = word_tokenize(text)
    return ' '.join([word for word in tokens if word not in stop_words])

# Function to detect misspelled offensive words
def contains_offensive_words(text, offensive_words, threshold=80):
    words = text.split()
    for word in words:
        for offensive_word in offensive_words:
            if fuzz.ratio(word, offensive_word) >= threshold:
                return True  # Offensive word detected
    return False

# Read input from PHP
if len(sys.argv) > 1:
    user_input = sys.argv[1]
else:
    print("ERROR: No input received.")
    sys.exit(1)

# Preprocess input
cleaned = clean_text(user_input)

# Check for explicit offensive words (including misspelled words)
if contains_offensive_words(cleaned, offensive_words):
    print("OFFENSIVE_LANGUAGE_DETECTED")
    sys.exit(0)

# Remove stopwords
processed = remove_stopwords(cleaned)

# Transform text into vector format
test_vector = tfidf.transform([processed]).toarray()

# Get prediction probabilities
probabilities = model.predict_proba(test_vector)[0]

# Set classification threshold (Lowered to 80%)
threshold = 0.80

# Class mapping
class_mapping = {0: "OFFENSIVE_LANGUAGE_DETECTED", 1: "HATE_SPEECH_DETECTED", 2: "SAFE_MESSAGE"}

# Get predicted class and probability
predicted_class = model.predict(test_vector)[0]
confidence = probabilities[predicted_class]

# Classify based on confidence
if confidence >= threshold:
    print(class_mapping[predicted_class])
else:
    print("SAFE_MESSAGE")
