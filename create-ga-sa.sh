# Get the project id
GCP_PROJECT_ID=(gcloud config get-value project)

# Create the GitHub actions service account
GA_SA_ID=ga-${RANDOM}

gcloud iam service-accounts create ${GA_SA_ID} \
  --description "GitHub actions service account" \
  --display-name "GitHub actions service account"

# Add the required roles to the service account
GCP_SA_EMAIL=$(gcloud iam service-accounts list --filter "email ~ ^${GA_SA_ID}" --format 'value(email)')

gcloud projects add-iam-policy-binding ${GCP_PROJECT_ID} \
  --member serviceAccount:${GCP_SA_EMAIL} \
  --role roles/run.admin \
  --role roles/storage.admin \
  --role roles/artifactregistry.admin

# Download the JSON keyfile for the service account
gcloud iam service-accounts keys create ga-keys.json --iam-account ${GCP_SA_EMAIL}
