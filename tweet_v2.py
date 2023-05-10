import pandas as pd
import nltk
import re
import pickle
import sys
import json
import subprocess
from nltk.corpus import stopwords
from nltk.stem import SnowballStemmer
from sklearn.linear_model import LogisticRegression
from sklearn.model_selection import train_test_split
from sklearn.feature_extraction.text import TfidfVectorizer


def err_out(msg, status="failure"):
    program_output["status"] = status
    program_output["msg"] = msg
    print(json.dumps(program_output))
    sys.exit(1)


program_output = {
    "status": "success",
    "msg": "none"
}

if len(sys.argv) != 2:
    err_out("Incorrect number of arguments (expected 1). Usage: python3 tweet_v2.py type")

prediction_type = sys.argv[1]

train = False
##############################
# TRAINING STUFF
##############################
if train:
    nltk.download('stopwords')
    # read in dataset
    df = pd.read_csv('newdataset.csv', encoding='latin', header=None)

    # I basically had to frankenstien two datasets and since there was 16 milllion rows, it was so messy so now im
    # doing voodoo to balance the distribution of the dataset I frankensteined
    # df1 has all the nazi tweets
    df1 = df.iloc[0:48575, :]
    # df2 has all the normal tweets
    df2 = df.iloc[1000000:1048575, :]
    # combining df1 and df2 so now instead of 16 million rows there is roughly 100,000
    df = pd.concat([df1, df2], axis=0)

    # adding column names to the dataset
    df.columns = ['sentiments', 'id', 'date', 'query', 'user', 'tweet']

    # keeping only two of the columns from dataset
    df = df[['sentiments', 'tweet']]

    # grabs list of words that are useless in finding sentiments such as "the", "is", etc
    stop_words = stopwords.words('english')

    # meant for stemming words example: cared -> care
    stemmer = SnowballStemmer('english')

    # removing unnecessary strings
    text_cleaning_re = "@\S+|https?:\S+|http?:\S|[^A-Za-z0-9]+"


    # tokenizes words
    def preprocess(text, stem=False):
        text = re.sub(text_cleaning_re, ' ', str(text).lower()).strip()
        tokens = []
        for token in text.split():
            if token not in stop_words:
                if stem:
                    tokens.append(stemmer.stem(token))
                else:
                    tokens.append(token)
        return " ".join(tokens)


    # applies tokens to every tweet
    df.tweet = df.tweet.apply(lambda x: preprocess(x))

    # separates dataframe into list so it can be put in train test split
    tweet, sentiments = list(df['tweet']), list(df['sentiments'])

    # train test split
    X_train, X_test, y_train, y_test = train_test_split(tweet, sentiments, test_size=0.05, random_state=0)

    # vectorizes the data
    vectoriser = TfidfVectorizer(ngram_range=(1, 2), max_features=50000)
    vectoriser.fit(X_train)

    X_train = vectoriser.transform(X_train)
    X_test = vectoriser.transform(X_test)

    # does logistic regression
    LRmodel = LogisticRegression(C=2, max_iter=1000, n_jobs=-1)
    LRmodel.fit(X_train, y_train)

    # creates pickle files
    file = open('vectoriser', 'wb')
    pickle.dump(vectoriser, file)
    file.close()

    file = open('Sentiment-LR.pickle', 'wb')
    pickle.dump(LRmodel, file)
    file.close()

##############################
# PREDICTION STUFF
##############################
# opens pickle files
file = open('./vectoriser', 'rb')
vectoriser = pickle.load(file)
file.close()
file = open('./Sentiment-LR.pickle', 'rb')
LRmodel = pickle.load(file)
file.close()


def predict1(vectoriser, model, tweet):
    textdata = vectoriser.transform(tweet)
    sentiment = model.predict(textdata)
    result = []
    pred_label = "Normal"
    for single_tweet, pred in zip(tweet, sentiment):
        if pred == 0:
            pred_label = "Malicious"
        result.append({'tweet': single_tweet, 'sentiment': pred_label})
    return result


tweet = []

if prediction_type == "link":
    tapi = subprocess.run(['python3', 'tweet_fetch.py'], stdout=subprocess.PIPE, text=True)
    tweet.append(tapi.stdout.strip())

if prediction_type == "text":
    with open('tweety', 'r') as file:
        for line in file:
            tweet.append(line.strip())

prediction_results = json.dumps(predict1(vectoriser, LRmodel, tweet))
program_output["msg"] = prediction_results
print(json.dumps(program_output))
