#!/bin/bash
set -e

# get the project id and number
GCP_PROJECT_ID=$(gcloud config get-value project)
GCP_PROJECT_NUMBER=$(gcloud projects list --filter "${GCP_PROJECT_ID}" --format "value(PROJECT_NUMBER)")

# create the GitHub actions service account
GA_SA_ID=ga-${RANDOM}

gcloud iam service-accounts create ${GA_SA_ID} \
  --description "GitHub actions service account" \
  --display-name "GitHub actions service account"

# add the required roles to the service account
GA_SA_EMAIL=$(gcloud iam service-accounts list --filter "email ~ ^${GA_SA_ID}" --format "value(email)")

gcloud projects add-iam-policy-binding ${GCP_PROJECT_ID} \
  --member serviceAccount:${GA_SA_EMAIL} \
  --role roles/run.developer

gcloud projects add-iam-policy-binding ${GCP_PROJECT_ID} \
  --member serviceAccount:${GA_SA_EMAIL} \
  --role roles/storage.objectAdmin

gcloud projects add-iam-policy-binding ${GCP_PROJECT_ID} \
  --member serviceAccount:${GA_SA_EMAIL} \
  --role roles/storage.objectViewer

gcloud projects add-iam-policy-binding ${GCP_PROJECT_ID} \
  --member serviceAccount:${GA_SA_EMAIL} \
  --role roles/artifactregistry.writer

gcloud iam service-accounts add-iam-policy-binding ${GCP_PROJECT_NUMBER}-compute@developer.gserviceaccount.com \
  --member serviceAccount:${GA_SA_EMAIL} \
  --role roles/iam.serviceAccountUser

# download the JSON keyfile for the service account
gcloud iam service-accounts keys create ga-keys.json --iam-account ${GA_SA_EMAIL}
