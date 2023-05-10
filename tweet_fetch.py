from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

# Configure Chrome options to run headless
chrome_options = Options()
chrome_options.add_argument('--headless')
chrome_options.add_argument('--no-sandbox')
chrome_options.add_argument('--disable-dev-shm-usage')

# Create a new instance of the Firefox driver
driver = webdriver.Chrome(options=chrome_options)

# Get tweet links
tweet = []
with open('tweety_links', 'r') as file:
    for line in file:
        tweet.append(line.strip())

for link in tweet:
    # Navigate to the desired URL
    driver.get(line)

    try:
        # Wait until the element with data-testid "theTweet" is present
        element = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.XPATH, '//div[@data-testid="tweetText"]//span'))
        )
        # Extract the text content and print it
        print(element.text)
    finally:
        driver.quit()