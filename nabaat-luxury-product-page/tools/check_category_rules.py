"""Self-check for includes/category-rules.json against a NABAAT Product Exporter export.

Usage: python -I tools/check_category_rules.py path/to/products.json
Every product must map to exactly the categories it has on the site; prints mismatches.
"""
import json, re, html, sys, os

rules = json.load(open(os.path.join(os.path.dirname(__file__), '..', 'includes', 'category-rules.json'), encoding='utf-8'))['rules']
products = json.load(open(sys.argv[1], encoding='utf-8'))['products']


def path_for(sku, name):  # mirror of Nabaat_Category_Assigner::path_for()
    for r in rules:
        if not r['sku'] and not r.get('name'):
            continue
        if r['sku'] and not re.search(r['sku'], sku, re.I):
            continue
        if r.get('name') and r['name'] not in name.lower():
            continue
        return r['path']
    return None


bad = [p for p in products if sorted(path_for(p['sku'], p['name']) or []) != sorted(html.unescape(c) for c in p['categories'])]
for p in bad:
    print('MISMATCH', p['sku'], path_for(p['sku'], p['name']), [html.unescape(c) for c in p['categories']])
print('checked %d products, %d mismatches' % (len(products), len(bad)))
sys.exit(1 if bad else 0)
