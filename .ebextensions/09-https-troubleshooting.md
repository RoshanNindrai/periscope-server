# HTTPS Timeout Troubleshooting

If HTTPS is timing out while HTTP works, check these AWS configurations:

## 1. Load Balancer Listener (Most Common Issue)

**Check:** EC2 Console > Load Balancers > Your EB Load Balancer > Listeners tab

**Required:**
- Listener on port **443** with protocol **HTTPS**
- SSL certificate attached (ACM certificate for your domain)
- Default action: Forward to target group (port 80)

**To Fix:**
1. Go to Elastic Beanstalk Console > Your Environment > Configuration
2. Click "Load balancer" > Edit
3. Add listener: Port 443, Protocol HTTPS
4. Select your SSL certificate from ACM
5. Default action: Forward to target group
6. Save and wait for environment update

## 2. Security Group Rules

**Check:** EC2 Console > Security Groups > Your EB Security Group > Inbound Rules

**Required:**
- Rule allowing **HTTPS (443)** from **0.0.0.0/0** or your IP range

**To Fix:**
1. Go to Security Groups > Your EB security group
2. Edit inbound rules
3. Add rule: Type=HTTPS, Port=443, Source=0.0.0.0/0
4. Save rules

## 3. SSL Certificate

**Check:** ACM Console > Certificates

**Required:**
- Valid certificate for `periscope.uniqlabs.co` (or your domain)
- Certificate must be in **us-east-1** region (same as EB environment)

**To Fix:**
- Request a new certificate in ACM if missing
- Verify domain ownership
- Attach certificate to load balancer listener

## 4. DNS Configuration

**Check:** Your DNS provider (Squarespace)

**Required:**
- CNAME record pointing to your EB environment URL
- Or A record pointing to load balancer IP

## Quick Test

After configuring HTTPS listener:
```bash
curl -I https://periscope.uniqlabs.co
```

Should return HTTP 200, not timeout.
