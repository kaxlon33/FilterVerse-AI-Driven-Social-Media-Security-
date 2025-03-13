import pandas as pd
import nltk
nltk.download('punkt_tab')
nltk.download('punkt')


# Load the dataset
data = pd.read_csv("D:/CRP/AM_app/AM_app/offensive-lang-env/HateSpeechData.csv")
print(data.head())

# Filter relevant columns
data = data[['tweet', 'class']]
data.dropna(inplace=True)  # Drop rows with missing values


print(data['class'].value_counts())

import re

def clean_text(text):
    text = re.sub(r"http\S+|www\S+|https\S+", '', text, flags=re.MULTILINE)
    text = re.sub(r'\@\w+|\#', '', text)
    text = re.sub(r'[^\w\s]', '', text)
    return text.lower()

data['cleaned_tweet'] = data['tweet'].apply(clean_text)

import nltk
from nltk.corpus import stopwords
from nltk.tokenize import word_tokenize

nltk.download('stopwords')
nltk.download('punkt')
stop_words = set(stopwords.words('english'))

def remove_stopwords(text):
    tokens = word_tokenize(text)
    return ' '.join([word for word in tokens if word not in stop_words])

data['processed_tweet'] = data['cleaned_tweet'].apply(remove_stopwords)


from sklearn.feature_extraction.text import TfidfVectorizer

tfidf = TfidfVectorizer(max_features=5000)  # Limit features for simplicity
X = tfidf.fit_transform(data['processed_tweet']).toarray()
y = data['class']


from sklearn.model_selection import train_test_split

X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)

from sklearn.linear_model import LogisticRegression

model = LogisticRegression()
model.fit(X_train, y_train)


from sklearn.metrics import classification_report, accuracy_score

y_pred = model.predict(X_test)
print("Accuracy:", accuracy_score(y_test, y_pred))
print(classification_report(y_test, y_pred))


import joblib

joblib.dump(model, 'offensive_language_model.pkl')
joblib.dump(tfidf, 'tfidf_vectorizer.pkl')
