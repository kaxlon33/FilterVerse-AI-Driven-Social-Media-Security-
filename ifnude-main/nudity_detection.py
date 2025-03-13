import sys
import cv2
from ifnude import detect

image_path = sys.argv[1]  # Path passed from PHP


image = cv2.imread(image_path)

if image is None:
    print("Error loading image!")
    sys.exit(1)

# Detect nudity
results = detect(image)

if results:
    print("NUDITY_DETECTED")
else:
    print("NO_NUDITY")
