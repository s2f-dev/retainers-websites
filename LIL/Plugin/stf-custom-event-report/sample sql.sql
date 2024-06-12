SELECT pm.post_id,
		p.ID,
		pm.meta_id,
		pm.meta_value,
		p.post_title,
		(
			SELECT
				SUM(
					IF(
						pmv.meta_key = '_stock',
						pmv.meta_value,
						NULL
					)
				)
			FROM
				lhq_posts pv
			JOIN lhq_postmeta pmv ON
				pv.ID = pmv.post_id
			WHERE
				pv.post_parent = p.ID 
		) AS variations_stock,
		(
			SELECT
				pmv.meta_value
			FROM
				lhq_posts pv
			JOIN lhq_postmeta pmv ON
				pv.ID = pmv.post_id
			WHERE
				pv.post_parent = p.ID 
			AND 
				pmv.meta_key = '_stock' 
			LIMIT 1 
		) AS inservice_stock,
		(
			SELECT
				pmv.meta_value
			FROM
				lhq_posts pv
			JOIN lhq_postmeta pmv ON
				pv.ID = pmv.post_id
			WHERE
				pv.post_parent = p.ID 
			AND 
				pmv.meta_key = '_stock' 
			LIMIT 1 
			OFFSET 1 
		) AS public_stock,
		(
			SELECT
				COUNT(*)
			FROM
				lhq_posts
			JOIN lhq_postmeta ON lhq_posts.ID = lhq_postmeta.post_id
			WHERE
				lhq_postmeta.meta_key = 'WooCommerceEventsProductID' AND lhq_postmeta.meta_value = p.ID AND lhq_posts.post_status = 'publish'
		) AS 'sales',
		(
			SELECT
				COUNT(*)
			FROM
				lhq_postmeta pms
			WHERE
				pms.meta_key = 'WooCommerceEventsVariationID' 
			AND 
				pms.meta_value 
			IN (
					SELECT pvv.ID 
					FROM lhq_posts pvv 
					WHERE pvv.post_type = 'product_variation' 
					AND pvv.post_parent = p.ID
					AND pvv.ID = (SELECT pt.ID FROM lhq_posts pt WHERE post_excerpt = 'Type: In-Service' AND pt.post_parent = p.ID)
				) 
		) AS 'inservice_sales',
		(
			SELECT
				COUNT(*)
			FROM
				lhq_postmeta pms
			WHERE
				pms.meta_key = 'WooCommerceEventsVariationID' 
			AND 
				pms.meta_value 
			IN (
					SELECT pvv.ID 
					FROM lhq_posts pvv 
					WHERE pvv.post_type = 'product_variation' 
					AND pvv.post_parent = p.ID
					AND pvv.ID = (SELECT pt.ID FROM lhq_posts pt WHERE post_excerpt = 'Type: Public' AND pt.post_parent = p.ID)
				) 
		) AS 'public_sales',
		(
			SELECT
				meta_value
			FROM
				lhq_postmeta
			WHERE
				post_id = p.ID AND meta_key = '_stock'
		) AS 'stock'
	FROM
		lhq_posts p
	INNER JOIN lhq_postmeta pm ON
		p.ID = pm.post_id
	WHERE
		p.post_type = 'product' AND p.post_status = 'publish' AND pm.meta_key = 'WooCommerceEventsDate' AND pm.meta_value <> ''
	GROUP BY
		p.post_title
	ORDER BY
		pm.meta_value
	DESC;