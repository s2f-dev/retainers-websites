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
				COUNT(*)
			FROM
				lhq_posts
			JOIN lhq_postmeta ON lhq_posts.ID = lhq_postmeta.post_id
			WHERE
				lhq_postmeta.meta_key = 'WooCommerceEventsProductID' AND lhq_postmeta.meta_value = p.ID AND lhq_posts.post_status = 'publish'
		) AS 'sales',
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